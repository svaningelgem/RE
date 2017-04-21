#include "stdafx.h"


BOOL APIENTRY DllMain( HMODULE hModule,
  DWORD  ul_reason_for_call,
  LPVOID lpReserved
) {
  switch ( ul_reason_for_call ) {
  case DLL_PROCESS_ATTACH: // 1
  case DLL_PROCESS_DETACH: //0
  case DLL_THREAD_ATTACH:  // 2
  case DLL_THREAD_DETACH:  // 3
    break;
  }
  return TRUE;
}
