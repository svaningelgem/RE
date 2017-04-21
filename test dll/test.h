#pragma once




#define BASE(X, name, VIRTUAL) \
class LIBRARY_API name##Base##X { \
public: \
  name##Base##X() \
  : X##publicVar1(1), \
    X##publicVar2(2), \
    X##protectedVar1(3), \
    X##protectedVar2(4), \
    X##privateVar1(5), \
    X##privateVar2(6) \
{ } \
 \
  VIRTUAL ~name##Base##X() { \
    X##publicVar1 = 999; \
  } \
 \
  int X##publicVar1; \
  int X##publicVar2; \
  int X##publicFunc1( int a ) { return 0; } \
  VIRTUAL const char* X##publicFunc2( const char* a, long b ) { return 0; } \
 \
protected: \
  int X##protectedVar1; \
  int X##protectedVar2; \
  void * X##protectedFunc1( short a ) { return 0; } \
  VIRTUAL void X##protectedFunc2( double a ) { return; } \
 \
private: \
  int X##privateVar1; \
  int X##privateVar2; \
  VIRTUAL void X##privateFunc1() { return; } \
  void X##privateFunc2() { return; } \
}

#define BASECLASS(X, name) \
{\
public: \
  name() \
    : X##pubVar( 1 ) { \
  } \
  ~name() { \
    X##pubVar = 999; \
  } \
 \
  int X##pubVar; \
  int X##pubFunc() { return 0; } \
}


#define SINGLEDERIVED(X, name, Y, Y1) \
class LIBRARY_API Single##name##Derived##Y##X : Y Y1 \
BASECLASS(X, Single##name##Derived##Y##X)

#define MULTIDERIVED(X, name, Y, Y1, Z, Z1) \
class LIBRARY_API Multi##name##Derived##Y##Z##X : Y Y1, Z Z1 \
BASECLASS(X, Multi##name##Derived##Y##Z##X)




BASE( A, Normal, );
BASE( B, Normal, );
SINGLEDERIVED( C, , public, NormalBaseA );
SINGLEDERIVED( D, , protected, NormalBaseA );
SINGLEDERIVED( E, , private, NormalBaseA );
MULTIDERIVED( F, , public, NormalBaseA, public, NormalBaseB );
MULTIDERIVED( G, , public, NormalBaseA, protected, NormalBaseB );
MULTIDERIVED( H, , public, NormalBaseA, private, NormalBaseB );
MULTIDERIVED( I, , protected, NormalBaseA, public, NormalBaseB );
MULTIDERIVED( J, , protected, NormalBaseA, protected, NormalBaseB );
MULTIDERIVED( K, , protected, NormalBaseA, private, NormalBaseB );
MULTIDERIVED( L, , private, NormalBaseA, public, NormalBaseB );
MULTIDERIVED( M, , private, NormalBaseA, protected, NormalBaseB );
MULTIDERIVED( N, , private, NormalBaseA, private, NormalBaseB );



BASE( A, Virtual, virtual );
BASE( B, Virtual, virtual );
SINGLEDERIVED( C, Virtual, public, VirtualBaseA );
SINGLEDERIVED( D, Virtual, protected, VirtualBaseA );
SINGLEDERIVED( E, Virtual, private, VirtualBaseA );
MULTIDERIVED( F, Virtual, public, virtual VirtualBaseA, public, VirtualBaseB );
MULTIDERIVED( G, Virtual, public, virtual VirtualBaseA, protected, VirtualBaseB );
MULTIDERIVED( H, Virtual, public, virtual VirtualBaseA, private, VirtualBaseB );
MULTIDERIVED( I, Virtual, protected, virtual VirtualBaseA, public, VirtualBaseB );
MULTIDERIVED( J, Virtual, protected, virtual VirtualBaseA, protected, VirtualBaseB );
MULTIDERIVED( K, Virtual, protected, virtual VirtualBaseA, private, VirtualBaseB );
MULTIDERIVED( L, Virtual, private, virtual VirtualBaseA, public, VirtualBaseB );
MULTIDERIVED( M, Virtual, private, virtual VirtualBaseA, protected, VirtualBaseB );
MULTIDERIVED( N, Virtual, private, virtual VirtualBaseA, private, VirtualBaseB );
